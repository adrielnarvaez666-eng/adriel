using System;
using System.Drawing;
using System.Drawing.Drawing2D;
using System.Windows.Forms;

internal static class Program
{
    [STAThread]
    private static void Main()
    {
        Application.EnableVisualStyles();
        Application.SetCompatibleTextRenderingDefault(false);
        Application.Run(new CartesianPlaneForm());
    }
}

internal sealed class CartesianPlaneForm : Form
{
    private const int DivisionCount = 10;

    public CartesianPlaneForm()
    {
        Text = "Plano cartesiano";
        ClientSize = new Size(800, 650);
        MinimumSize = new Size(480, 400);
        BackColor = Color.White;
        DoubleBuffered = true;
        StartPosition = FormStartPosition.CenterScreen;
    }

    protected override void OnPaint(PaintEventArgs e)
    {
        base.OnPaint(e);

        Graphics graphics = e.Graphics;
        graphics.SmoothingMode = SmoothingMode.AntiAlias;

        const float leftMargin = 70;
        const float topMargin = 65;
        const float rightMargin = 45;
        const float bottomMargin = 65;

        float plotWidth = ClientSize.Width - leftMargin - rightMargin;
        float plotHeight = ClientSize.Height - topMargin - bottomMargin;
        if (plotWidth <= 0 || plotHeight <= 0)
        {
            return;
        }

        RectangleF plot = new RectangleF(leftMargin, topMargin, plotWidth, plotHeight);
        float cellWidth = plot.Width / DivisionCount;
        float cellHeight = plot.Height / DivisionCount;

        using (Font titleFont = new Font(Font.FontFamily, 14, FontStyle.Bold))
        using (Font labelFont = new Font(Font.FontFamily, 9, FontStyle.Regular))
        using (Font axisFont = new Font(Font.FontFamily, 11, FontStyle.Bold))
        using (Pen gridPen = new Pen(Color.LightGray, 1))
        using (Pen axisPen = new Pen(Color.Black, 2))
        using (Brush textBrush = new SolidBrush(Color.Black))
        using (Brush backgroundBrush = new SolidBrush(BackColor))
        {
            StringFormat centered = new StringFormat
            {
                Alignment = StringAlignment.Center,
                LineAlignment = StringAlignment.Center
            };

            graphics.DrawString("Plano cartesiano (primer cuadrante)", titleFont, textBrush,
                new RectangleF(0, 15, ClientSize.Width, 30), centered);

            for (int value = 0; value <= DivisionCount; value++)
            {
                float x = plot.Left + value * cellWidth;
                float y = plot.Bottom - value * cellHeight;
                graphics.DrawLine(gridPen, x, plot.Top, x, plot.Bottom);
                graphics.DrawLine(gridPen, plot.Left, y, plot.Right, y);

                if (value > 0)
                {
                    graphics.DrawString(value.ToString(), labelFont, textBrush,
                        new RectangleF(x - 15, plot.Bottom + 7, 30, 20), centered);
                    graphics.DrawString(value.ToString(), labelFont, textBrush,
                        new RectangleF(plot.Left - 40, y - 10, 30, 20), centered);
                }
            }

            graphics.DrawLine(axisPen, plot.Left, plot.Bottom, plot.Right + 8, plot.Bottom);
            graphics.DrawLine(axisPen, plot.Left, plot.Bottom, plot.Left, plot.Top - 8);
            DrawArrow(graphics, axisPen, plot.Right + 8, plot.Bottom, true);
            DrawArrow(graphics, axisPen, plot.Left, plot.Top - 8, false);

            graphics.FillRectangle(backgroundBrush,
                plot.Left - 12, plot.Bottom + 5, 24, 22);
            graphics.DrawString("0", labelFont, textBrush,
                new RectangleF(plot.Left - 12, plot.Bottom + 5, 24, 20), centered);

            graphics.DrawString("X", axisFont, textBrush,
                new RectangleF(plot.Right - 3, plot.Bottom + 27, 30, 25), centered);
            graphics.DrawString("Y", axisFont, textBrush,
                new RectangleF(plot.Left - 38, plot.Top - 14, 25, 25), centered);
        }
    }

    private static void DrawArrow(Graphics graphics, Pen pen, float x, float y, bool pointsRight)
    {
        PointF[] arrow = pointsRight
            ? new[] { new PointF(x, y), new PointF(x - 9, y - 5), new PointF(x - 9, y + 5) }
            : new[] { new PointF(x, y), new PointF(x - 5, y + 9), new PointF(x + 5, y + 9) };

        using (Brush brush = new SolidBrush(pen.Color))
        {
            graphics.FillPolygon(brush, arrow);
        }
    }
}
